import React, { useState, useEffect } from "react";
import axios from "axios";
import {
  Table,
  Button,
  Input,
  Modal,
  Form,
  Select,
  InputNumber,
  Checkbox,
  Space,
  message,
  Tag,
  Tooltip,
  Tabs,
  Badge,
} from "antd";
import {
  PlusOutlined,
  EditOutlined,
  DeleteOutlined,
  ReloadOutlined,
  SearchOutlined,
  FileExcelOutlined,
  HistoryOutlined,
  StockOutlined,
} from "@ant-design/icons";
import type { TabsProps } from "antd";

interface InventoryProps {
  apiUrl: string;
}

interface Product {
  id: number;
  sku: string;
  name: string;
  description: string | null;
  category: string | null;
  unit_price: number;
  tax_category: string;
  tax_rate: number;
  unit_of_measure: string;
  current_stock: number;
  reorder_level: number;
  track_inventory: boolean;
  active: boolean;
  created_at: string;
  updated_at: string;
}

interface Movement {
  id: number;
  inventory_id: number;
  movement_type: string;
  quantity: number;
  unit_price: number;
  reference: string;
  metadata: Record<string, any>;
  created_at: string;
}

const { TabPane } = Tabs;
const { Option } = Select;

const Inventory: React.FC<InventoryProps> = ({ apiUrl }) => {
  // State variables
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState<boolean>(false);
  const [modalVisible, setModalVisible] = useState<boolean>(false);
  const [currentProduct, setCurrentProduct] = useState<Product | null>(null);
  const [form] = Form.useForm();
  const [searchText, setSearchText] = useState<string>("");
  const [filterCategory, setFilterCategory] = useState<string>("");
  const [filterActive, setFilterActive] = useState<boolean | null>(null);
  const [categories, setCategories] = useState<string[]>([]);
  const [taxCategories, setTaxCategories] = useState<
    { code: string; name: string; rate: number }[]
  >([]);
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 10,
    total: 0,
  });
  const [movementModalVisible, setMovementModalVisible] =
    useState<boolean>(false);
  const [selectedProductMovements, setSelectedProductMovements] = useState<
    Movement[]
  >([]);
  const [selectedProductName, setSelectedProductName] = useState<string>("");
  const [adjustStockModalVisible, setAdjustStockModalVisible] =
    useState<boolean>(false);
  const [adjustStockForm] = Form.useForm();

  // Fetch products
  const fetchProducts = async (
    page = pagination.current,
    pageSize = pagination.pageSize,
    search = searchText,
    category = filterCategory,
    active = filterActive
  ) => {
    setLoading(true);
    try {
      const filters: Record<string, any> = {};
      if (category) filters.category = category;
      if (active !== null) filters.active = active;

      const response = await axios.post(`${apiUrl}/inventory/search`, {
        query: search,
        filters,
        limit: pageSize,
        offset: (page - 1) * pageSize,
      });

      setProducts(response.data.products || []);
      setPagination({
        ...pagination,
        current: page,
        total: response.data.total || 0,
      });

      // Extract unique categories for filter
      if (response.data.products && response.data.products.length > 0) {
        const uniqueCategories = Array.from(
          new Set(
            response.data.products
              .map((p: Product) => p.category)
              .filter(Boolean)
          )
        );
        setCategories(uniqueCategories as string[]);
      }
    } catch (error) {
      console.error("Error fetching products:", error);
      message.error("Failed to load inventory products");
    } finally {
      setLoading(false);
    }
  };

  // Fetch tax categories
  const fetchTaxCategories = async () => {
    try {
      const response = await axios.get(`${apiUrl}/tax/categories`);
      setTaxCategories(response.data);
    } catch (error) {
      console.error("Error fetching tax categories:", error);
      message.error("Failed to load tax categories");
    }
  };

  // Handle product form submission
  const handleFormSubmit = async (values: any) => {
    try {
      if (currentProduct) {
        // Update existing product
        await axios.put(
          `${apiUrl}/inventory/products/${currentProduct.id}`,
          values
        );
        message.success("Product updated successfully");
      } else {
        // Create new product
        await axios.post(`${apiUrl}/inventory/products`, values);
        message.success("Product created successfully");
      }

      setModalVisible(false);
      fetchProducts(); // Refresh product list
    } catch (error) {
      console.error("Error saving product:", error);
      message.error("Failed to save product");
    }
  };

  // Handle delete product
  const handleDeleteProduct = async (id: number) => {
    try {
      await axios.delete(`${apiUrl}/inventory/products/${id}`);
      message.success("Product deleted successfully");
      fetchProducts(); // Refresh product list
    } catch (error) {
      console.error("Error deleting product:", error);
      message.error("Failed to delete product");
    }
  };

  // Handle view product movements
  const handleViewMovements = async (
    productId: number,
    productName: string
  ) => {
    setLoading(true);
    try {
      const response = await axios.get(
        `${apiUrl}/inventory/products/${productId}/movements`
      );
      setSelectedProductMovements(response.data);
      setSelectedProductName(productName);
      setMovementModalVisible(true);
    } catch (error) {
      console.error("Error fetching movements:", error);
      message.error("Failed to load movement history");
    } finally {
      setLoading(false);
    }
  };

  // Handle adjust stock
  const handleAdjustStock = (product: Product) => {
    setCurrentProduct(product);
    adjustStockForm.setFieldsValue({
      quantity: product.current_stock,
      reason: "Manual adjustment",
    });
    setAdjustStockModalVisible(true);
  };

  // Submit stock adjustment
  const handleStockAdjustmentSubmit = async (values: any) => {
    if (!currentProduct) return;

    try {
      await axios.post(
        `${apiUrl}/inventory/products/${currentProduct.id}/adjust`,
        values
      );
      message.success("Stock adjusted successfully");
      setAdjustStockModalVisible(false);
      fetchProducts(); // Refresh product list
    } catch (error) {
      console.error("Error adjusting stock:", error);
      message.error("Failed to adjust stock");
    }
  };

  // Effect to load data on component mount
  useEffect(() => {
    fetchProducts();
    fetchTaxCategories();
  }, []);

  // Table columns
  const columns = [
    {
      title: "SKU",
      dataIndex: "sku",
      key: "sku",
      sorter: (a: Product, b: Product) => a.sku.localeCompare(b.sku),
    },
    {
      title: "Name",
      dataIndex: "name",
      key: "name",
      sorter: (a: Product, b: Product) => a.name.localeCompare(b.name),
    },
    {
      title: "Category",
      dataIndex: "category",
      key: "category",
      filters: categories.map((cat) => ({ text: cat, value: cat })),
      onFilter: (value: string, record: Product) => record.category === value,
    },
    {
      title: "Price",
      dataIndex: "unit_price",
      key: "unit_price",
      render: (price: number) => `$${price.toFixed(2)}`,
      sorter: (a: Product, b: Product) => a.unit_price - b.unit_price,
    },
    {
      title: "Tax",
      dataIndex: "tax_category",
      key: "tax_category",
      render: (category: string, record: Product) => (
        <Tooltip title={`${record.tax_rate}%`}>
          <Tag
            color={
              category === "VAT"
                ? "blue"
                : category === "ZERO_RATED"
                  ? "green"
                  : "orange"
            }
          >
            {category}
          </Tag>
        </Tooltip>
      ),
    },
    {
      title: "Stock",
      dataIndex: "current_stock",
      key: "current_stock",
      render: (stock: number, record: Product) => (
        <Badge
          count={stock}
          showZero
          overflowCount={9999}
          style={{
            backgroundColor:
              stock <= 0
                ? "#f5222d"
                : stock <= record.reorder_level
                  ? "#faad14"
                  : "#52c41a",
            fontWeight: "bold",
          }}
        />
      ),
      sorter: (a: Product, b: Product) => a.current_stock - b.current_stock,
    },
    {
      title: "Status",
      key: "status",
      render: (text: string, record: Product) => (
        <Space>
          {record.active ? (
            <Tag color="green">Active</Tag>
          ) : (
            <Tag color="red">Inactive</Tag>
          )}
          {!record.track_inventory && <Tag color="purple">No Tracking</Tag>}
        </Space>
      ),
    },
    {
      title: "Actions",
      key: "actions",
      render: (text: string, record: Product) => (
        <Space size="small">
          <Button
            type="text"
            icon={<EditOutlined />}
            onClick={() => {
              setCurrentProduct(record);
              form.setFieldsValue({
                ...record,
                description: record.description || "",
                category: record.category || "",
              });
              setModalVisible(true);
            }}
          />
          <Button
            type="text"
            icon={<StockOutlined />}
            onClick={() => handleAdjustStock(record)}
          />
          <Button
            type="text"
            icon={<HistoryOutlined />}
            onClick={() => handleViewMovements(record.id, record.name)}
          />
          <Button
            type="text"
            danger
            icon={<DeleteOutlined />}
            onClick={() => {
              Modal.confirm({
                title: "Confirm Delete",
                content: `Are you sure you want to delete ${record.name}?`,
                onOk: () => handleDeleteProduct(record.id),
              });
            }}
          />
        </Space>
      ),
    },
  ];

  // Movement history columns
  const movementColumns = [
    {
      title: "Date",
      dataIndex: "created_at",
      key: "created_at",
      render: (date: string) => new Date(date).toLocaleString(),
    },
    {
      title: "Type",
      dataIndex: "movement_type",
      key: "movement_type",
      render: (type: string) => {
        const colors: Record<string, string> = {
          SALE: "red",
          PURCHASE: "green",
          ADJUSTMENT: "blue",
          INITIAL: "purple",
          RETURN: "orange",
        };
        return <Tag color={colors[type] || "default"}>{type}</Tag>;
      },
    },
    {
      title: "Quantity",
      dataIndex: "quantity",
      key: "quantity",
      render: (qty: number) => (
        <span style={{ color: qty < 0 ? "#ff4d4f" : "#52c41a" }}>
          {qty > 0 ? "+" : ""}
          {qty}
        </span>
      ),
    },
    {
      title: "Unit Price",
      dataIndex: "unit_price",
      key: "unit_price",
      render: (price: number) => `$${price.toFixed(2)}`,
    },
    {
      title: "Reference",
      dataIndex: "reference",
      key: "reference",
    },
    {
      title: "Notes",
      key: "metadata",
      render: (record: Movement) => {
        if (!record.metadata) return null;

        // Format metadata for display
        const metadata = record.metadata;
        return (
          <Tooltip
            title={
              <div>
                {Object.entries(metadata).map(([key, value]) => (
                  <div key={key}>
                    <strong>{key}:</strong>{" "}
                    {typeof value === "object"
                      ? JSON.stringify(value)
                      : String(value)}
                  </div>
                ))}
              </div>
            }
          >
            <Button type="link" size="small">
              View Details
            </Button>
          </Tooltip>
        );
      },
    },
  ];

  // Render dashboard tabs
  const items: TabsProps["items"] = [
    {
      key: "1",
      label: "Products",
      children: (
        <>
          <div
            style={{
              marginBottom: 16,
              display: "flex",
              justifyContent: "space-between",
            }}
          >
            <Space>
              <Input
                placeholder="Search products..."
                value={searchText}
                onChange={(e) => setSearchText(e.target.value)}
                prefix={<SearchOutlined />}
                style={{ width: 250 }}
                onPressEnter={() =>
                  fetchProducts(
                    1,
                    pagination.pageSize,
                    searchText,
                    filterCategory,
                    filterActive
                  )
                }
              />
              <Select
                style={{ width: 150 }}
                placeholder="Category"
                allowClear
                value={filterCategory || undefined}
                onChange={(value) => setFilterCategory(value)}
              >
                {categories.map((category) => (
                  <Option key={category} value={category}>
                    {category}
                  </Option>
                ))}
              </Select>
              <Select
                style={{ width: 120 }}
                placeholder="Status"
                allowClear
                value={filterActive === null ? undefined : filterActive}
                onChange={(value) => setFilterActive(value)}
              >
                <Option value={true}>Active</Option>
                <Option value={false}>Inactive</Option>
              </Select>
              <Button
                type="primary"
                icon={<SearchOutlined />}
                onClick={() =>
                  fetchProducts(
                    1,
                    pagination.pageSize,
                    searchText,
                    filterCategory,
                    filterActive
                  )
                }
              >
                Search
              </Button>
              <Button
                icon={<ReloadOutlined />}
                onClick={() => {
                  setSearchText("");
                  setFilterCategory("");
                  setFilterActive(null);
                  fetchProducts(1, pagination.pageSize, "", "", null);
                }}
              >
                Reset
              </Button>
            </Space>
            <Space>
              <Button
                type="primary"
                icon={<PlusOutlined />}
                onClick={() => {
                  setCurrentProduct(null);
                  form.resetFields();
                  setModalVisible(true);
                }}
              >
                Add Product
              </Button>
              <Button
                icon={<FileExcelOutlined />}
                onClick={() =>
                  message.info("Export functionality coming soon!")
                }
              >
                Export
              </Button>
            </Space>
          </div>
          <Table
            columns={columns}
            dataSource={products}
            rowKey="id"
            loading={loading}
            pagination={{
              ...pagination,
              onChange: (page, pageSize) => {
                fetchProducts(
                  page,
                  pageSize,
                  searchText,
                  filterCategory,
                  filterActive
                );
              },
            }}
          />
        </>
      ),
    },
    {
      key: "2",
      label: "Low Stock",
      children: (
        <div>
          <p>Low stock items will be displayed here.</p>
        </div>
      ),
    },
  ];

  return (
    <div className="inventory-container">
      <h2>Inventory Management</h2>

      <Tabs defaultActiveKey="1" items={items} />

      {/* Product Add/Edit Modal */}
      <Modal
        title={
          currentProduct
            ? `Edit Product: ${currentProduct.name}`
            : "Add New Product"
        }
        open={modalVisible}
        onCancel={() => setModalVisible(false)}
        footer={null}
        width={800}
      >
        <Form
          form={form}
          layout="vertical"
          onFinish={handleFormSubmit}
          initialValues={
            currentProduct || {
              active: true,
              track_inventory: true,
              unit_of_measure: "EACH",
              tax_category: "VAT",
              reorder_level: 10,
            }
          }
        >
          <div style={{ display: "flex", gap: "16px" }}>
            <div style={{ flex: 1 }}>
              <Form.Item
                name="sku"
                label="SKU"
                rules={[{ required: true, message: "Please enter SKU" }]}
              >
                <Input />
              </Form.Item>

              <Form.Item
                name="name"
                label="Product Name"
                rules={[
                  { required: true, message: "Please enter product name" },
                ]}
              >
                <Input />
              </Form.Item>

              <Form.Item name="category" label="Category">
                <Input />
              </Form.Item>

              <Form.Item name="description" label="Description">
                <Input.TextArea rows={3} />
              </Form.Item>
            </div>

            <div style={{ flex: 1 }}>
              <Form.Item
                name="unit_price"
                label="Unit Price"
                rules={[{ required: true, message: "Please enter unit price" }]}
              >
                <InputNumber
                  min={0}
                  precision={2}
                  style={{ width: "100%" }}
                  formatter={(value) =>
                    `$ ${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                  }
                  parser={(value) => value!.replace(/\$\s?|(,*)/g, "")}
                />
              </Form.Item>

              <Form.Item
                name="tax_category"
                label="Tax Category"
                rules={[
                  { required: true, message: "Please select tax category" },
                ]}
              >
                <Select>
                  {taxCategories.map((category) => (
                    <Option key={category.code} value={category.code}>
                      {category.name} ({category.rate}%)
                    </Option>
                  ))}
                </Select>
              </Form.Item>

              <Form.Item
                name="unit_of_measure"
                label="Unit of Measure"
                rules={[
                  { required: true, message: "Please select unit of measure" },
                ]}
              >
                <Select>
                  <Option value="EACH">Each</Option>
                  <Option value="KG">Kilogram</Option>
                  <Option value="LITER">Liter</Option>
                  <Option value="METER">Meter</Option>
                  <Option value="PACK">Pack</Option>
                </Select>
              </Form.Item>

              {!currentProduct && (
                <Form.Item name="initial_stock" label="Initial Stock">
                  <InputNumber min={0} style={{ width: "100%" }} />
                </Form.Item>
              )}

              <Form.Item name="reorder_level" label="Reorder Level">
                <InputNumber min={0} style={{ width: "100%" }} />
              </Form.Item>
            </div>
          </div>

          <div style={{ display: "flex", gap: "16px" }}>
            <Form.Item
              name="track_inventory"
              valuePropName="checked"
              style={{ flex: 1 }}
            >
              <Checkbox>Track Inventory</Checkbox>
            </Form.Item>

            <Form.Item
              name="active"
              valuePropName="checked"
              style={{ flex: 1 }}
            >
              <Checkbox>Active</Checkbox>
            </Form.Item>
          </div>

          <Form.Item>
            <div
              style={{
                display: "flex",
                justifyContent: "flex-end",
                gap: "8px",
              }}
            >
              <Button onClick={() => setModalVisible(false)}>Cancel</Button>
              <Button type="primary" htmlType="submit">
                {currentProduct ? "Update" : "Create"}
              </Button>
            </div>
          </Form.Item>
        </Form>
      </Modal>

      {/* Movement History Modal */}
      <Modal
        title={`Movement History: ${selectedProductName}`}
        open={movementModalVisible}
        onCancel={() => setMovementModalVisible(false)}
        footer={null}
        width={800}
      >
        <Table
          columns={movementColumns}
          dataSource={selectedProductMovements}
          rowKey="id"
          loading={loading}
          pagination={{ pageSize: 10 }}
        />
      </Modal>

      {/* Adjust Stock Modal */}
      <Modal
        title={`Adjust Stock: ${currentProduct?.name}`}
        open={adjustStockModalVisible}
        onCancel={() => setAdjustStockModalVisible(false)}
        footer={null}
      >
        <Form
          form={adjustStockForm}
          layout="vertical"
          onFinish={handleStockAdjustmentSubmit}
        >
          <Form.Item
            name="quantity"
            label="New Quantity"
            rules={[{ required: true, message: "Please enter quantity" }]}
          >
            <InputNumber min={0} style={{ width: "100%" }} />
          </Form.Item>

          <Form.Item
            name="reason"
            label="Reason"
            rules={[{ required: true, message: "Please enter reason" }]}
          >
            <Input.TextArea rows={2} />
          </Form.Item>

          <Form.Item>
            <div
              style={{
                display: "flex",
                justifyContent: "flex-end",
                gap: "8px",
              }}
            >
              <Button onClick={() => setAdjustStockModalVisible(false)}>
                Cancel
              </Button>
              <Button type="primary" htmlType="submit">
                Adjust Stock
              </Button>
            </div>
          </Form.Item>
        </Form>
      </Modal>
    </div>
  );
};

export default Inventory;
